<?php
    // Be careful of this.  Turns off the execution limit, you may want to set it to something more like 300
    ini_set('max_execution_time', 0);

    // Set to path of yout comic repository, it will open files recursively
    $process_files  = glob('T:/Comics/*/*/*.cbz');
    //$process_files  = glob('D:/MegaLinks/get/*/*.cbz');
    $ban_file       = 'D:/comics/bans.txt';
    $cache_file     = 'D:/comics/cache.txt';

    $bans = file($ban_file, FILE_IGNORE_NEW_LINES);
    $cache = file($cache_file, FILE_IGNORE_NEW_LINES);

    $array_size = count($process_files);
    $x = 1;

    foreach($process_files as $zip_file){
        echo "Processing file $x of $array_size \r";

        if(in_array(basename($zip_file),$cache)){
            //echo "File " . basename($zip_file) . " has already been processed.  Moving to next" . PHP_EOL;
            $x++;
            continue;
        } else {
            $cache = cache_processed_file($zip_file,$cache,$cache_file);
        }

        $zip = new ZipArchive;

        if ($zip->open($zip_file) === TRUE) {
            echo PHP_EOL . 'Now processing -- ' . basename($zip_file) . PHP_EOL .PHP_EOL;

            // Find file name from archive.  Must be tagged with ComicTagger to work
            preg_match('/^(.+#[-.0-9]+).+$/',$zip_file,$match);

            $clean_file = $match[1];
            $clean_file = str_replace('#', '', $clean_file);

            $max = $zip->numFiles;

            $contents = array();
            $remove_folders = array();

            // Loops through files in archive and pushes found folders to an array, we'll deal with them later
            for($i = 0; $i < $max; $i++){
                $stat = $zip->statIndex($i);
                if ($stat['size']){
                    $contents[] = $stat['name'];
                } else {
                    $remove_folders[] = $stat['name'];
                }
            }

            $folders_to_remove = count($remove_folders);

            // Sorts array numerically to ensure proper page order
            natsort($contents);

            $counter = 1;
            foreach($contents as $comic){
                $file_name = (basename($comic) . PHP_EOL );
                $delete_me = $comic;
                $folder_name = dirname($delete_me);

                $ext = preg_match('/^.+(\..*)$/',$delete_me,$match);
                $extension = isset($match[1]) ? $match[1] : '/';

                $new_name = file_naming_convention($counter,$clean_file,$extension);

                $delete_me = convert_case($delete_me,$zip);

                $bans = check_for_new_bans($delete_me,$bans,$ban_file,$comic);

                $is_valid = check_for_invalid($comic,$delete_me,$zip_file,$zip);

                // Deletes anything from the ban list
                if (in_array(basename($delete_me), $bans)) {
                    echo PHP_EOL . "Deleting --- " . basename($comic) . " from file - " . basename($zip_file) . PHP_EOL;
                    $zip->deleteName($delete_me);
                    continue;
                }

                // Renames the files to match the archive name + page number
                if($is_valid){
                    echo "\t- Renaming - $delete_me to " . basename($new_name) . PHP_EOL;
                    $zip->renameName($delete_me,basename($new_name));
                }
                $counter++;
            }
            echo PHP_EOL . '-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --' . PHP_EOL;

            // Removes any folders found in the archive but only after they are emptied
            if($folders_to_remove > 0){
                foreach($remove_folders as $delFolder){
                    $zip->deleteName($delFolder);
                }
            }
            $zip->close();
        } else {
            echo 'failed';
        }
        $x++;
    }
    echo PHP_EOL . 'All Done!' . PHP_EOL;

    function convert_case($file,$zip){
        // Converts uppercase file extension to lower  case, I am anal about consistency
        if(preg_match('/^.*\.[A-Z]{1}[aA-zZ]+$/', $file)){
            $rename = pathinfo($file, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if(isset($folder_name)){
                $zip->renameName($file,"$folder_name/$rename");
            } else {
                $zip->renameName($file,"$rename");
            }
            return($rename);
        } else {
            return($file);
        }
    }

    function file_naming_convention($cnt,$clean,$ext){
        // Adds number padding to page number
        if($cnt < 10){
            $num = "00$cnt";
        } elseif ($cnt >= 10 and $cnt < 100){
            $num = "0$cnt";
        } else {
            $num = $cnt;
        }
        return($clean . ' - ' . $num . "$ext");
    }

    function check_for_invalid($c,$d,$z,$zip){
        // Global check which will match any sfv,nfo, log or txt file (with a few others from some watermarks).  Deletes file if found
        if(preg_match('/^.*\.sfv$|^zX.*$|^.*\.nfo$|^.*\.md5$|^.*\.htm$|^.*\.html$|^.*\.txt$|^.*Scanned By.*|^.*ResinDCP.*$|^.*resindcp.*$|^.*Resin-DCP.*$|^.*\.log$|cookiemonster/',basename($d))){
            echo PHP_EOL . "Deleting --- " . basename($c) . " from file - " . basename($z) . PHP_EOL;
            $zip->deleteName($d);
            return(false);
        } else {
            return(true);
        }
    }

    function check_for_new_bans($delete,$bans,$bans_file){
        // Looking for files that more than likely should've been banned and adds them to the ban list if found.
        if(preg_match('/^[zZ].*$|^[xX]{2}.*$|^[yY]{2}.*$|^xtag.*$/',basename($delete))){
            if (!in_array(basename($delete),$bans)) {
                echo PHP_EOL . basename($delete) . " was not found in ban list but probably should be" . PHP_EOL;
                echo PHP_EOL . "Adding " . basename($delete) . " to bans list" . PHP_EOL;
                file_put_contents($bans_file, PHP_EOL . basename($delete), FILE_APPEND);
                $bans = file($bans_file, FILE_IGNORE_NEW_LINES);
                return($bans);
            } else {
                return($bans);
            }
        } else {
            return($bans);
        }
    }

    // Cache file check, this way we don't have to rename every single file each time we add something new
    function cache_processed_file($file,$cache,$cache_file){
        if (!in_array(basename($file),$cache)) {
            echo PHP_EOL . "Caching file -- " . basename($file) . PHP_EOL;
            file_put_contents($cache_file, PHP_EOL . basename($file), FILE_APPEND);
            $cache = file($cache_file, FILE_IGNORE_NEW_LINES);
            return($cache);
        } else {
            return($cache);
        }
    }
?>

<?php
    if (ob_get_level() == 0) ob_start();

    // Be careful of this.  Turns off the execution limit, you may want to set it to something more like 300
    ini_set('max_execution_time', 0);

    // Set to path of yout comic repository, it will open files recursively
    $process_files = glob('/mnt/data_drive/Comics/*/*/*.cbz');

    // Set to ban file location
    $ban_file = 'D:/Comics/bans.txt';
    $bans = file($ban_file, FILE_IGNORE_NEW_LINES);
    $array_size = count($process_files);
    $x = 1;

    $options = getopt("r:");
    foreach($process_files as $zip_file){
        $zip = new ZipArchive;
        //echo "Processing file $x of $array_size \r";

        if ($zip->open($zip_file) === TRUE) {
            echo PHP_EOL . 'Now processing -- ' . basename($zip_file) . PHP_EOL .PHP_EOL;

            // Find file name from archive.  Must be tagged with ComicTagger to work
            preg_match('/^(.+#\d+).+$/',$zip_file,$match);

            $clean_file = $match[1];
            $clean_file = str_replace('#', '', $clean_file);

            $max = $zip->numFiles;

            // This section is for listing the last 2 files in the zip, if there are any files I missed from my bans, they would be here
            //$last_file = $zip->statIndex($max - 1);
            //$second_to_last_file = $zip->statIndex($max - 2);

            //echo $second_to_last_file['name'] .  PHP_EOL;
            //echo $last_file['name'] .  PHP_EOL . PHP_EOL;


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
            sort($contents);

            $counter = 1;
            foreach($contents as $comic){
                $file_name = (basename($comic) . PHP_EOL );
                $delete_me = $comic;
                $folder_name = dirname($delete_me);

                $ext = preg_match('/^.+(\..*)$/',$delete_me,$match);
                $extension = isset($match[1]) ? $match[1] : '/';

                // Adds number padding to page number
                if($counter < 10){
                    $num = "00$counter";
                } elseif ($counter >= 10 and $counter < 100){
                    $num = "0$counter";
                } else {
                    $num = $counter;
                }

                $new_name = $clean_file . ' - ' . $num . "$extension";

                // Converts uppercase file extension to lower  case, I am anal about consistency
                if(preg_match('/^.*\.[A-Z]{1}[aA-zZ]+$/', $delete_me)){
                    $rename = pathinfo($delete_me, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($delete_me, PATHINFO_EXTENSION));
                    $zip->renameName($delete_me,"$folder_name/$rename");
                    $delete_me = $rename;
                }

                // Global check which will match any sfv,nfo, log or txt file (with a few others from some watermarks).  Deletes file if found
                if(preg_match('/^.*\.sfv$|^.*\.nfo$|^.*\.txt$|^.*Scanned By.*|^.*ResinDCP.*$|^.*resindcp.*$|^.*Resin-DCP.*$|^.*\.log$/',basename($delete_me))){
                    echo PHP_EOL . "Deleting --- " . basename($comic) . " from file - " . basename($zip_file) . PHP_EOL;
                    $zip->deleteName($delete_me);
                    continue;
                }

                // Deletes anything from the ban list
                if (in_array(basename($delete_me), $bans)) {
                    echo PHP_EOL . "Deleting --- " . basename($comic) . " from file - " . basename($zip_file) . PHP_EOL;
                    $zip->deleteName($delete_me);
                    continue;
                }

                // Renames the files to match the archive name + page number
                echo "\t- Renaming - $delete_me to " . basename($new_name) . PHP_EOL;
                $zip->renameName($delete_me,basename($new_name));

                $counter++;
            }
            echo PHP_EOL . '-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --' . PHP_EOL;

            // Removes any folders found in the archive but only after they are emptied
            if($folders_to_remove > 0){
                foreach($remove_folders as $delFolder){
                    $zip->deleteName($delFolder);
                }
            }

            ob_flush();
            flush();
            $zip->close();
        } else {
            echo 'failed';
        }
        $x++;
    }
    echo PHP_EOL . 'All Done!' . PHP_EOL;
?>

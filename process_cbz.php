<?php

    if (ob_get_level() == 0) ob_start();

    // Be careful of this.  Turns off the execution limit, you may want to set it to something more like 300
    ini_set('max_execution_time', 0);

    // Set to path of yout comic repository, it will open files recursively
    $process_files = glob('/mnt/data_drive/Comics/*/*/*.cbz');
    $ban_file = 'bans.txt';
    $bans = file($ban_file, FILE_IGNORE_NEW_LINES);

    foreach($process_files as $zip_file){
        $zip = new ZipArchive;

        if ($zip->open($zip_file) === TRUE) {
            echo '<pre>';
            echo 'Now processing -- ' . basename($zip_file) . PHP_EOL;

            for( $i = 0; $i < $zip->numFiles; $i++ ){
                $stat = $zip->statIndex( $i );
                $file_name = (basename( $stat['name'] ) . PHP_EOL );
                $delete_me = $stat['name'];

                // Matching files that begin with z or ZZ and removing them, haven't run into an issue (yet) with comic jpg files using that naming scheme
                if(preg_match("/.+\.SFV|.+\.nfo|^z.*\.jpg|^ZZ.*\.jpg/",basename($delete_me))){
                    echo "Deleting --- $file_name";
                    $zip->deleteName($delete_me);
                    continue;
                }

                if (in_array(basename($delete_me), $bans)) {
                    echo "Deleting --- $file_name";
                    $zip->deleteName($delete_me);
                }
                // Used this echo when trying to figure out which files to ban
                #echo $file_name;
            }

            echo '</pre>';

            ob_flush();
            flush();

            $zip->close();
        } else {
            echo 'failed';
        }
    }
    echo 'All Done!';
?>

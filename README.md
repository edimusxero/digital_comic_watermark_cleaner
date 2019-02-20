# digital_comic_watermark_cleaner 
I have a large library of digital comic files which I have accumulated over the years, the only problem is the annoying watermark images that the up loaders insert into the archive. This script is an attempt to mass clean those. 

Initial release is just a simple php script with no flair. Future versions might look nicer but aesthetics was not my intent, functionality was.

As of now this only processes .cbz files.  All files have been tagged using comic tagger  (https://github.com/davide-romanini/comictagger)

** You don't need to use ComicTagger to name your files.  This will work as long as the comic file is in the following format

        <name> <# issue number> <(date)>.cbz


Initially created to run on a web server but it will indeed work from a command line (the output isn't optomized for this but it works)


Use with caution, I do not take responsibilty for you nuking your library.

There is a now a caching file which files that are processed will be added to.  Items on this list are skipped next run.  As of now the file needs to exist to run.  Will implement automatically creating the file if not present in future updates.

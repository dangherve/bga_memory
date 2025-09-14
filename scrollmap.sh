#/bin/bash

set -e

rep=~/code/bga_scrollmap

fileTocopy="core_patch_slideto.js scrollmapWithZoom.js long-press-event.js"

currentRep=`pwd`

cd "$rep"

git checkout main
git pull
git fetch

lastestTag=`git describe --tags --abbrev=0`

git checkout $lastestTag

cd $currentRep

for file in $fileTocopy; do
    scp $rep/$file modules/
done

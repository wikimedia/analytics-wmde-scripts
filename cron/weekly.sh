#!/bin/bash -x
# @author Addshore
#
# This script should be run every sunday at 01 hours
# The first parameter should be the directory this repo is checked out into.
# Scripts that are run here need to be executable (+x) in git.

if [ -z "$1" ]
  then
    date +"%F %T weekly.sh No argument supplied!"
    exit 1
fi
date +"%F %T weekly.sh Started!"

# dbtables
eval "$1/src/dbtables/slots.php" &

date +"%F %T weekly.sh Waiting!"
wait

date +"%F %T weekly.sh Ended!"

#!/bin/bash -x
# @author Addshore
#
# This script should be run every sunday at 01 hours
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    date +"%F %T weekly.sh No argument supplied!"
    exit 1
fi
date +"%F %T weekly.sh Started!"

#noop

date +"%F %T weekly.sh Ended!"
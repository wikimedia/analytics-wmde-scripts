#!/bin/bash
# @author Addshore
#
# This script should be run every sunday at 01 hours
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    echo "No argument supplied"
    exit 1
fi

#noop
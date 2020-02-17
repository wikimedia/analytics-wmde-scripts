<?php

// Setting run time limit to one hour for all scripts
// The assumption being that the slowest script should not run more than an hour.

const RUN_TIME_LIMIT = 3600;
set_time_limit( RUN_TIME_LIMIT );

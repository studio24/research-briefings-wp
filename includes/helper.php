<?php

/**
 * Build an array of research briefing years from 1989 to current year
 *
 * @return array
 */
function get_year_list()
{
    $year = range(1989, date('Y'));

    // Sort the array in a descending order
    rsort($year);

    return $year;
}



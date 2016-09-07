<?php

function build_log_link($file, $cur_file)
{
    $lbl = rtrim($file['name'], '.php');
    $lbl = ltrim($lbl, 'log-');
    $class = '';

    if ($file['name'] == $cur_file)
    {
        $class = 'active';
    }

    return "<a href=\"?file=$file[name]\" class=\"$class\">$lbl</a>\n";
}

function build_filter_link($filter, $label)
{
    $CI = getInstance();
    $params['filter'] = $CI->input->get('filter') === FALSE ? 'all' : $CI->input->get('filter');
    $class = '';

    if (isset($params['filter']) and $filter == $params['filter'])
    {
        $class = 'active';
    }
    else if (!isset($params['filter']) and $filter == 'all')
    {
        $class = 'active';
    }

    $params['filter'] = $filter;

    return "<a href=\"?file=" . $CI->log_file . "&filter=$filter\" class=\"$class\">$label</a>";
}

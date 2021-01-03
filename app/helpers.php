<?php

// 转换路由名称
function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}

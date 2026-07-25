<?php
declare(strict_types=1);

namespace NCache\Enum;

enum CType{
    case ARRAY;
    case JSON;
    case STRING;
}
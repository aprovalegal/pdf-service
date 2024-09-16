<?php

namespace App\Enums;

enum PdfWatermarkStatus: int
{
    case PENDING = 1;
    case FINISHED = 2;
    case ERROR = 3;
}

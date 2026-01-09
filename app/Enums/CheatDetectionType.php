<?php

namespace App\Enums;

enum CheatDetectionType: string
{
    case TabSwitch = 'tab_switch';
    case WindowBlur = 'window_blur';
    case KeyboardShortcut = 'keyboard_shortcut';
    case RightClick = 'right_click';
    case CopyPaste = 'copy_paste';
    case TimeAnomaly = 'time_anomaly';
    case MultipleDevices = 'multiple_devices';
}


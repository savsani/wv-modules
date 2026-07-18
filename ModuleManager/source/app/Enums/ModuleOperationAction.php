<?php

namespace Modules\ModuleManager\Enums;

enum ModuleOperationAction: string
{
    case Install = 'install';
    case Update = 'update';
    case Migrate = 'migrate';
}

<?php

namespace App\ProfilePro\Public\Query;

interface ProTimezoneQueryInterface{
    public function getProTimezone(int $proId): string;
}

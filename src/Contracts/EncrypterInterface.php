<?php

namespace BogdanKharchenko\Settings\Contracts;

interface EncrypterInterface
{
    public function encrypt($value);

    public function decrypt($value);
}

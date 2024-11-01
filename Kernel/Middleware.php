<?php

namespace Kernel;

Interface Middleware {
    public function handle($pipeline);
}
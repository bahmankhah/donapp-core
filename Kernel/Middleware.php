<?php

namespace Kernel;

Interface Middleware {
    public function handle($request, Pipeline $pipeline);
}
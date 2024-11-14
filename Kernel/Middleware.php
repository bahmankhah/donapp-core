<?php

namespace Kernel;

interface Middleware {
    public function handle($request, Pipeline $pipeline);
}
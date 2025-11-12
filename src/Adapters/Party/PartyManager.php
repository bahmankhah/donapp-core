<?php

namespace App\Adapters\Party;
use Kernel\Adapters\AdapterManager;

class PartyManager extends AdapterManager{
    
    public function getKey(): string{
        return 'party';
    }
    
}
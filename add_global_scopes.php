<?php
protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('is_professional', function ($builder) {
            if (!auth()->check()) {
                $builder->where('is_professional', 0);
            }
        });
    }

    ?>
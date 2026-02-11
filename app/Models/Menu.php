<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;

class Menu extends Model
{
    protected $table = 'menu';
    protected $guarded = ['id'];

    // protected $fillable = ['name', 'icon', 'route', 'parent_id', 'order'];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'menu_permission');
    }
}

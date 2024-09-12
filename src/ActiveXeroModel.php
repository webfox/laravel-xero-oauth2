<?php

namespace Webfox\Xero;

use Illuminate\Database\Eloquent\Model;

class ActiveXeroModel
{
    protected Model $model;

    public function setActiveModel(Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}

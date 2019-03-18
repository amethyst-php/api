<?php

namespace Railken\Amethyst\Api\Support;

use Illuminate\Support\Facades\Config;
use Railken\Lem\Contracts\AgentContract;

class Helper
{
    public static function getManagerByModel(string $class)
    {
        foreach (array_keys(Config::get('amethyst')) as $config) {
            foreach (Config::get('amethyst.'.$config.'.data', []) as $data) {
                if (isset($data['model']) && ($class === $data['model'] || is_subclass_of($class, $data['model']))) {
                    return $data['manager'];
                }
            }
        }

        return null;
    }

    public static function newManagerByModel(string $classModel, AgentContract $agent = null)
    {
        $class = static::getManagerByModel($classModel);

        if (!$class) {
            throw new \Exception(sprintf('Missing %s', $classModel));
        }

        return new $class($agent);
    }
}

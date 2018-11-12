<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Utility;

use Cake\I18n\Time;
use InvalidArgumentException;

/**
 * Class responsible for generating Magic values.
 */
final class MagicValue
{
    /**
     * Magic value wrapper identifier.
     */
    const WRAPPER = '%%';

    /**
     * User info.
     *
     * @var array
     */
    private $user = [];

    /**
     * Field value.
     *
     * @var string
     */
    private $value = '';

    /**
     * Constructor method.
     *
     * @param string $value Field value
     * @param mixed[] $user User info
     * @return void
     */
    public function __construct(string $value, array $user)
    {
        if (empty($user)) {
            throw new InvalidArgumentException('User info are required.');
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Value must be a string.');
        }

        $this->user = $user;
        $this->value = $value;
    }

    /**
     * Magic value getter.
     *
     * @return mixed
     */
    public function get()
    {
        $value = str_replace(static::WRAPPER, '', $this->value);

        if (! method_exists($this, $value)) {
            return $this->value;
        }

        return $this->{$value}();
    }

    /**
     * Current user id magic value getter.
     *
     * @return string
     */
    private function me(): string
    {
        return $this->user['id'];
    }

    /**
     * Today's date magic value getter.
     *
     * @return \Cake\I18n\Time
     */
    private function today(): \Cake\I18n\Time
    {
        return new Time('today');
    }

    /**
     * Yesterday's date magic value getter.
     *
     * @return \Cake\I18n\Time
     */
    private function yesterday(): \Cake\I18n\Time
    {
        return new Time('yesterday');
    }
    /**
     * Tomorrow's date magic value getter.
     *
     * @return \Cake\I18n\Time
     */
    private function tomorrow(): \Cake\I18n\Time
    {
        return new Time('tomorrow');
    }
}

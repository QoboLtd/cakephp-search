<?php
namespace Search\Model\Entity;

use Cake\ORM\Entity;

/**
 * Report Entity
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property string $user_id
 * @property string $model
 * @property string $content
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \Search\Model\Entity\User $user
 */
class Report extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}

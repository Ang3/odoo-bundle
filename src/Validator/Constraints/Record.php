<?php

namespace Ang3\Bundle\OdooBundle\Validator\Constraints;

use Ang3\Component\Odoo\Expression\DomainInterface;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Record extends Constraint
{
    /**
     * @var string
     *
     * @required
     */
    public $model;

    /**
     * @var DomainInterface[]|DomainInterface|array|string|null
     */
    public $domains;

    /**
     * @var string
     */
    public $connection = 'default';

    /**
     * @var string
     */
    public $typeErrorMessage = 'This value must be a positive integer.';

    /**
     * @var string
     */
    public $notFoundMessage = 'The record of ID {{ model_id }} from "{{ model_name }}" was not found.';

    public function getDefaultOption(): string
    {
        return 'model';
    }
}

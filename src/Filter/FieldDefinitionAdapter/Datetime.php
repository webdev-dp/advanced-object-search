<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;

class Datetime extends Numeric implements FieldDefinitionAdapterInterface
{
    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = 'datetime';

    /**
     * @return array
     */
    public function getESMapping()
    {
        if ($this->considerInheritance) {
            return [
                $this->fieldDefinition->getName(),
                [
                    'properties' => [
                        self::ES_MAPPING_PROPERTY_STANDARD => [
                            'type' => 'date',
                        ],
                        self::ES_MAPPING_PROPERTY_NOT_INHERITED => [
                            'type' => 'date',
                        ]
                    ]
                ]
            ];
        } else {
            return [
                $this->fieldDefinition->getName(),
                [
                    'type' => 'date',
                ]
            ];
        }
    }

    /**
     * @param array|string $fieldFilter
     *
     * filter field format as follows:
     *   - simple date like
     *       2017-02-26   --> creates TermQuery
     *   - array with gt, gte, lt, lte like
     *      ["gte" => 2017-02-26, "lte" => 2017-05-26] --> creates RangeQuery
     * @param bool $ignoreInheritance
     * @param string $path
     *
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = '')
    {
        if (is_array($fieldFilter)) {
            foreach ($fieldFilter as &$value) {
                $datetime = new \DateTime($value);
                $value = $datetime->format(\DateTimeInterface::ATOM);
            }

            return new RangeQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance), $fieldFilter);
        } else {
            $datetime = new \DateTime($fieldFilter);
            $datetime = $datetime->format(\DateTimeInterface::ATOM);

            return new TermQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance), $datetime);
        }
    }

    /**
     * @param Concrete $object
     * @param bool $ignoreInheritance
     */
    protected function doGetIndexDataValue($object, $ignoreInheritance = false)
    {
        $inheritanceBackup = null;
        if ($ignoreInheritance) {
            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues(false);
        }

        $value = null;

        $getter = 'get' . $this->fieldDefinition->getName();
        $valueObject = $object->$getter();
        if ($valueObject) {
            $value = $valueObject->format(\DateTimeInterface::ATOM);
        }

        if ($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        return $value;
    }
}

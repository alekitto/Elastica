<?php

namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\SpanNear;
use Elastica\Query\SpanTerm;
use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;

class SpanNearTest extends BaseTest
{
    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testConstructWrongTypeInvalid()
    {
        $term1 = new Term(['name' => 'marek']);
        $term2 = new Term(['name' => 'nicolas']);
        $spanNearQuery = new SpanNear([$term1, $term2]);
    }

    /**
     * @group unit
     */
    public function testConstructValid()
    {
        $field = 'name';
        $spanTermQuery1 = new SpanTerm($field, 'marek', 1.5);
        $spanTermQuery2 = new SpanTerm($field, 'nicolas');

        $spanNearQuery = new SpanNear([$spanTermQuery1, $spanTermQuery2], 5, true);

        $expected = [
            'span_near' => [
                'clauses' => [
                    [
                        'span_term' => [
                            'name' => [
                                'value' => 'marek',
                                'boost' => 1.5,
                            ],
                        ],
                    ],
                    [
                        'span_term' => [
                            'name' => [
                                'value' => 'nicolas',
                                'boost' => 1,
                            ],
                        ],
                    ],

                ],
                'slop' => 5,
                'in_order' => true,
            ],
        ];

        $this->assertEquals($expected, $spanNearQuery->toArray());
    }

    /**
     * @group functional
     */
    public function testSpanNearTerm()
    {
        $field = 'lorem';
        $value = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse odio lacus, aliquam nec nulla quis, aliquam eleifend eros.';

        $index = $this->_createIndex();
        $type = $index->getType('test');

        $docHitData = [$field => $value];
        $doc = new Document(1, $docHitData);
        $type->addDocument($doc);
        $index->refresh();

        $spanTermQuery1 = new SpanTerm($field, 'adipiscing');
        $spanTermQuery2 = new SpanTerm($field, 'lorem');

        //slop range 4 won't match
        $spanNearQuery = new SpanNear([$spanTermQuery1, $spanTermQuery2], 4);
        $resultSet = $type->search($spanNearQuery);
        $this->assertEquals(0, $resultSet->count());

        //slop range 4 will match
        $spanNearQuery->setSlop(5);
        $resultSet = $type->search($spanNearQuery);
        $this->assertEquals(1, $resultSet->count());

        //in_order set to true won't match
        $spanNearQuery->setInOrder(true);
        $resultSet = $type->search($spanNearQuery);
        $this->assertEquals(0, $resultSet->count());

        $spanNearQuery->addClause(new SpanTerm($field, 'consectetur'));
        $spanNearQuery->setInOrder(false);
        $resultSet = $type->search($spanNearQuery);
        $this->assertEquals(1, $resultSet->count());
    }
}

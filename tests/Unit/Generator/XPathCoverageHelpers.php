<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use DOMDocument;
use DOMNodeList;
use DOMXPath;

/**
 * DOMXPath that returns a DOMNodeList containing a text node.
 * Used to cover defensive branches when item(0) is not DOMElement.
 * Use with a document that has a text node, e.g. loadXML('<r>x</r>').
 */
final class XPathReturningTextNodeList extends DOMXPath
{
    private DOMNodeList $listWithTextNode;

    public function __construct(DOMDocument $document)
    {
        parent::__construct($document);
        $list = parent::query('//text()');
        $this->listWithTextNode = $list instanceof DOMNodeList ? $list : parent::query('//__nonexistent__');
    }

    public function query(string $expression, ?\DOMNode $contextNode = null, bool $registerNodeNS = true): DOMNodeList|false
    {
        return $this->listWithTextNode;
    }
}

/**
 * DOMXPath that returns an empty DOMNodeList.
 * Used to cover defensive return when length <= index.
 */
final class XPathReturningEmptyNodeList extends DOMXPath
{
    private DOMNodeList $emptyList;

    public function __construct(DOMDocument $document)
    {
        parent::__construct($document);
        $list = parent::query('//__nonexistent__');
        $this->emptyList = $list instanceof DOMNodeList ? $list : parent::query('//*');
    }

    public function query(string $expression, ?\DOMNode $contextNode = null, bool $registerNodeNS = true): DOMNodeList|false
    {
        return $this->emptyList;
    }
}

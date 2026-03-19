<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use DOMDocument;
use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use RuntimeException;

/**
 * DOMXPath that returns a DOMNodeList containing a text node.
 * Used to cover defensive branches when item(0) is not DOMElement.
 * Use with a document that has a text node, e.g. loadXML('<r>x</r>').
 */
final class XPathReturningTextNodeList extends DOMXPath
{
    /** @var DOMNodeList<DOMNode|DOMNameSpaceNode> */
    private DOMNodeList $listWithTextNode;

    public function __construct(DOMDocument $document)
    {
        parent::__construct($document);
        $list     = parent::query('//text()');
        $fallback = parent::query('//*');
        $assign   = ($list instanceof DOMNodeList && $list->length > 0) ? $list : ($fallback instanceof DOMNodeList ? $fallback : $list);
        if (!$assign instanceof DOMNodeList) {
            throw new RuntimeException('DOM query failed');
        }
        $this->listWithTextNode = $assign;
    }

    public function query(string $expression, ?DOMNode $contextNode = null, bool $registerNodeNS = true): DOMNodeList
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
    /** @var DOMNodeList<DOMNode|DOMNameSpaceNode> */
    private DOMNodeList $emptyList;

    public function __construct(DOMDocument $document)
    {
        parent::__construct($document);
        $list     = parent::query('//__nonexistent__');
        $fallback = parent::query('//*');
        $assign   = $list instanceof DOMNodeList ? $list : ($fallback instanceof DOMNodeList ? $fallback : $list);
        if (!$assign instanceof DOMNodeList) {
            throw new RuntimeException('DOM query failed');
        }
        $this->emptyList = $assign;
    }

    public function query(string $expression, ?DOMNode $contextNode = null, bool $registerNodeNS = true): DOMNodeList
    {
        return $this->emptyList;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Search\Model\Autocomplete;

/**
 * Class \Magento\Search\Model\Autocomplete\Item
 *
 */
class Item extends \Magento\Framework\DataObject implements ItemInterface
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTitle()
    {
        return $this->_getData('title');
    }
}
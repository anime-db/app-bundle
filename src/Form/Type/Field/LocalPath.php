<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Form\Type\Field;

use Symfony\Component\Form\AbstractType;

/**
 * Local path form field
 *
 * @package AnimeDb\Bundle\AppBundle\Form\Type\Field
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class LocalPath extends AbstractType
{
    /**
     * @return string
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'local_path';
    }
}

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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Rating form field
 *
 * @package AnimeDb\Bundle\AppBundle\Form\Type\Field
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Rating extends AbstractType
{
    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'expanded' => true,
            'required' => false,
            'empty_value' => false,
            'choices' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rating';
    }
}

<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Form\Type\Field\Image;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Image upload form
 */
class Upload extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remote', 'text', [
                'label' => 'Image URL',
                'required' => false
            ])
            ->add('local', 'file', [
                'label' => 'Upload image',
                'required' => false
            ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'image_popup';
    }
}

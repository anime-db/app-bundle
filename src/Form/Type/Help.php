<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Help type extension
 *
 * @package AnimeDb\Bundle\AppBundle\Form\Type
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Help extends AbstractTypeExtension
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractTypeExtension::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('help', $options['help']);
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractTypeExtension::buildView()
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['help'] = $form->getConfig()->getAttribute('help');
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractTypeExtension::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'help' => null,
        ]);
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeExtensionInterface::getExtendedType()
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
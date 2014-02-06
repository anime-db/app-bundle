<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Form\Field\LocalPath;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Local path choice form
 *
 * @package AnimeDb\Bundle\AppBundle\Form\Field\LocalPath
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Choice extends AbstractType
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('path', 'text', [
                'label' => 'Path',
                'required' => true,
                'attr' => [
                    'placeholder' => $this->getUserHomeDir()
                ]
            ]);

        // choice the disc letter in Windows
        if (defined('PHP_WINDOWS_VERSION_BUILD') &&
            extension_loaded('com_dotnet') &&
            ($fs = new \COM('Scripting.FileSystemObject'))
        ) {
            // types: Unknown, Removable, Fixed, Network, CD-ROM, RAM Disk
            $choices = [];
            foreach($fs->Drives as $drive) {
                $drive = $fs->GetDrive($drive);
                if($drive->DriveType == 3){
                    $name = $drive->Sharename;
                } elseif ($drive->IsReady) {
                    $name = $drive->VolumeName;
                } else {
                    $name = '[Drive not ready]';
                }
                $choices[$drive->DriveLetter] = $drive->DriveLetter . ': ' . $name;
            }
            $builder->add('letter', 'choice', ['choices' => $choices]);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'local_path_popup';
    }

    /**
     * Get user home dir
     *
     * @return string
     */
    protected function getUserHomeDir() {
        if ($home = getenv('HOME')) {
            $last = substr($home, strlen($home), 1);
            if ($last == '/' || $last == '\\') {
                return $home;
            } else {
                return $home.DIRECTORY_SEPARATOR;
            }
        } elseif (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            return '/home/'.get_current_user().'/';
        } elseif (is_dir($win7path = 'C:\Users\\'.get_current_user().'\\')) { // is Windows 7 or Vista
            return $win7path;
        } else {
            return 'C:\Documents and Settings\\'.get_current_user().'\\';
        }
    }
}
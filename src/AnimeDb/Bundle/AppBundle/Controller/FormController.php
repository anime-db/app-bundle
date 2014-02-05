<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use AnimeDb\Bundle\AppBundle\Entity\Field\Image as ImageField;
use AnimeDb\Bundle\AppBundle\Form\Field\Image\Upload as UploadImage;
use AnimeDb\Bundle\AppBundle\Form\Field\LocalPath\Choice as ChoiceLocalPath;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class FormController extends Controller
{
    /**
     * Form field local path
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function localPathAction(Request $request)
    {
        $response = new Response();
        // caching
        if ($last_update = $this->container->getParameter('last_update')) {
            $response->setPublic();
            $response->setLastModified(new \DateTime($last_update));

            // response was not modified for this request
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

        $form = $this->createForm(
            new ChoiceLocalPath(),
            ['path' => $request->get('path') ?: '']
        );

        return $this->render('AnimeDbAppBundle:Form:local_path.html.twig', [
            'form' => $form->createView()
        ], $response);
    }

    /**
     * Return list folders for path
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function localPathFoldersAction(Request $request)
    {
        $form = $this->createForm(new ChoiceLocalPath());
        $form->handleRequest($request);
        $path = $form->get('path')->getData() ?: $this->getUserHomeDir();

        if (($root = $request->get('root')) && strpos($path, $root) !== 0) {
            $path = $root;
        }

        if (!is_dir($path) || !is_readable($path)) {
            throw new NotFoundHttpException('Cen\'t read directory: '.$path);
        }

        // add slash if need
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $path .= $path[strlen($path)-1] != DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '';

        // caching
        $response = new JsonResponse();
        $response->setPublic();
        $response->setLastModified(new \DateTime('@'.filemtime($path)));
        if ( // poject update date
            ($last_update = $this->container->getParameter('last_update')) &&
            ($last_update = new \DateTime($last_update)) > $response->getLastModified()
        ) {
            $response->setLastModified($last_update);
        }

        // response was not modified for this request
        if ($response->isNotModified($request)) {
            return $response;
        }

        // scan directory
        $d = dir($path);
        $folders = [];
        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || !($realpath = realpath($path.$entry.DIRECTORY_SEPARATOR))) {
                continue;
            }
            if ($realpath[strlen($realpath)-1] != DIRECTORY_SEPARATOR) {
                $realpath .= DIRECTORY_SEPARATOR;
            }

            // if read path is root path then parent path is also equal to root
            if ($realpath != $path && is_dir($realpath) &&
                (($entry == '..' && (!$root || strpos($realpath, $root) === 0)) || $entry[0] != '.')
            ) {
                $folders[$entry] = [
                    'name' => $entry,
                    'path' => $realpath
                ];
            }
        }
        $d->close();
        ksort($folders);

        return $response->setData([
            'path' => $path,
            'folders' => array_values($folders)
        ]);
    }

    /**
     * Form field image
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function imageAction(Request $request) {
        return $this->render('AnimeDbAppBundle:Form:image.html.twig', [
            'form' => $this->createForm(new UploadImage())->createView(),
            'change' => (bool)$request->get('change', false)
        ]);
    }

    /**
     * Upload image
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function imageUploadAction(Request $request) {
        $image = new ImageField();
        /* @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new UploadImage(), $image);
        $form->handleRequest($request);

        if (!$form->isValid()) {
            $errors = $form->getErrors();
            return new JsonResponse(['error' => $this->get('translator')->trans($errors[0]->getMessage())], 404);
        }

        // try upload file
        try {
            $image->upload($this->get('validator'));
            return new JsonResponse([
                'path'  => $image->getPath(),
                'image' => $image->getWebPath(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $this->get('translator')->trans($e->getMessage())], 404);
        }
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
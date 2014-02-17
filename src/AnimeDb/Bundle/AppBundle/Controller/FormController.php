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
use AnimeDb\Bundle\AppBundle\Util\Filesystem;

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
        $path = $form->get('path')->getData() ?: Filesystem::getUserHomeDir();

        if (($root = $request->get('root')) && strpos($path, $root) !== 0) {
            $path = $root;
        }

        // add slash if need
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $path .= $path[strlen($path)-1] != DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '';
        $origin_path = $path;

        // wrap fs
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            stream_wrapper_register('win', 'Patchwork\Utf8\WinFsStreamWrapper');
            $path = 'win://'.$path;
        }

        if (!is_dir($path) || !is_readable($path)) {
            throw new NotFoundHttpException('Cen\'t read directory: '.$origin_path);
        }

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
        $folders = [];
        /* @var $file \SplFileInfo */
        foreach (new \DirectoryIterator($path) as $file) {
            if (
                !in_array($file->getFilename(), ['.', '..', '.Spotlight-V100', '.Trashes', 'pagefile.sys']) &&
                substr($file->getFilename(), -1) != '~' &&
                $file->getFilename()[0] != '.' &&
                $file->isDir() && $file->isReadable()
            ) {
                $folders[$file->getFilename()] = [
                    'name' => $file->getFilename(),
                    'path' => $origin_path.$file->getFilename().DIRECTORY_SEPARATOR
                ];
            }
        }
        ksort($folders);

        // add link on parent folder
        if (substr_count($origin_path, DIRECTORY_SEPARATOR) > 1) {
            $pos = strrpos(substr($origin_path, 0, -1), DIRECTORY_SEPARATOR) + 1;
            array_unshift($folders, [
                'name' => '..',
                'path' => substr($origin_path, 0, $pos)
            ]);
        }

        return $response->setData([
            'path' => $origin_path,
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

        return $this->render('AnimeDbAppBundle:Form:image.html.twig', [
            'form' => $this->createForm(new UploadImage())->createView(),
            'change' => (bool)$request->get('change', false)
        ], $response);
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
}
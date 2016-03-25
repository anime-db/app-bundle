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

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use AnimeDb\Bundle\AppBundle\Entity\Field\Image as ImageField;
use AnimeDb\Bundle\AppBundle\Form\Type\Field\Image\Upload as UploadImage;
use AnimeDb\Bundle\AppBundle\Form\Type\Field\LocalPath\Choice as ChoiceLocalPath;
use AnimeDb\Bundle\AppBundle\Util\Filesystem;

/**
 * Form
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class FormController extends BaseController
{
    /**
     * Form field local path
     *
     * @param Request $request
     *
     * @return Response
     */
    public function localPathAction(Request $request)
    {
        $response = $this->getCacheTimeKeeper()->getResponse();
        // response was not modified for this request
        if ($response->isNotModified($request)) {
            return $response;
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function localPathFoldersAction(Request $request)
    {
        $form = $this->createForm(new ChoiceLocalPath());
        $form->handleRequest($request);
        $path = $form->get('path')->getData() ?: Filesystem::getUserHomeDir();

        if (($root = $request->get('root')) && strpos($path, $root) !== 0) {
            $path = $root;
        }

        /* @var $response JsonResponse */
        $response = $this->getCacheTimeKeeper()
            ->getResponse([(new \DateTime)->setTimestamp(filemtime($path))], -1, new JsonResponse());
        // response was not modified for this request
        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response->setData([
            'path' => $path,
            'folders' => Filesystem::scandir($path, Filesystem::DIRECTORY)
        ]);
    }

    /**
     * Form field image
     *
     * @param Request $request
     *
     * @return Response
     */
    public function imageAction(Request $request) {
        $response = $this->getCacheTimeKeeper()->getResponse();
        // response was not modified for this request
        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->render('AnimeDbAppBundle:Form:image.html.twig', [
            'form' => $this->createForm(new UploadImage())->createView(),
            'change' => (bool)$request->get('change', false)
        ], $response);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function imageUploadAction(Request $request) {
        $image = new ImageField();
        /* @var $form Form */
        $form = $this->createForm(new UploadImage(), $image);
        $form->handleRequest($request);

        if (!$form->isValid()) {
            $errors = $form->getErrors();
            return new JsonResponse(['error' => $this->get('translator')->trans($errors[0]->getMessage())], 404);
        }

        // try upload file
        try {
            $this->get('anime_db.downloader')->imageField($image);
            return new JsonResponse([
                'path'  => $image->getFilename(),
                'image' => $image->getWebPath(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $this->get('translator')->trans($e->getMessage())], 404);
        }
    }
}

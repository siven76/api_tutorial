<?php


namespace App\Controller;


use App\Doctrine\UuidEncoder;
use App\Entity\BlogPost;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/add", name="blog_add", methods={"POST"})
     */
    public function add(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        $blogPost = $serializer->deserialize($request->getContent(), BlogPost::class, 'json');

        $em = $this->getDoctrine()->getManager();
        $em->persist($blogPost);
        $em->flush();

        return $this->json($blogPost);
    }

    /**
     * @Route("/{page}", name="blog_list", defaults={"page"=5}, requirements={"page"="\d+"}, methods={"GET"})
     */
    public function list($page, Request $request, UuidEncoder $uuidEncoder)
    {
        $repository = $this->getDoctrine()->getRepository(BlogPost::class);
        $items      = $repository->findAll();

        return $this->json(
            [
                'page'  => $page,
                'limit' => $request->get('limit', 10),
                'data'  => array_map(function (BlogPost $item) use ($uuidEncoder) {
                    return [
                        'id'   => $uuidEncoder->encode($item->getUuid()),
                        'slug' => $this->generateUrl('blog_by_slug', ['slug' => $item->getSlug()]),
                    ];
                }, $items),
            ]
        );
    }

    /**
     * @Route("/post/{id}", name="blog_by_id", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function post(BlogPost $post)
    {
        return $this->json($post);
    }

    /**
     * @Route("/post/{uuid}", name="blog_by_uuid", methods={"GET"})
     */
    public function postByUuid($uuid, UuidEncoder $uuidEncoder)
    {
        $uuid = $uuidEncoder->decode($uuid);

        return $this->json(
            $this->getDoctrine()->getRepository(BlogPost::class)->findBy(['uuid' => $uuid])
        );
    }

    /**
     * @Route("/post/{slug}", name="blog_by_slug", methods={"GET"})
     */
    public function postBySlug($slug)
    {
        return $this->json(
            $this->getDoctrine()->getRepository(BlogPost::class)->findBy(['slug' => $slug])
        );
    }

    /**
     * @Route("/post/{uuid}", name="blog_delete", methods={"DELETE"})
     */
    public function delete($uuid, UuidEncoder $uuidEncoder)
    {
        $uuid = $uuidEncoder->decode($uuid);

        $post = $this->getDoctrine()->getRepository(BlogPost::class)->findOneBy(['uuid' => $uuid]);

        if ($post) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($post);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
<?php

namespace App\Controller;

use App\Entity\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityTypesController extends AbstractController
{
    

    #[Route('/activity-types', name: 'get_all_activity_types', methods: ['GET'])]
    public function getAll(EntityManagerInterface $entityManager): JsonResponse
    {
        $activityTypes = $entityManager->getRepository(ActivityType::class)->findAll();
        $data = [];

        foreach ($activityTypes as $activityType) {
            $data[] = [
                'id' => $activityType->getId(),
                'name' => $activityType->getName(),
                'number_monitors' => $activityType->getNumberMonitors(),
            ];
        }

        return $this->json($data);
    }

}

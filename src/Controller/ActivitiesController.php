<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityType;
use App\Entity\Monitor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ActivitiesController extends AbstractController
{
    #[Route('/activities/{date?}', name: 'get_activities', methods: ['GET'], requirements: ["date" => "\d{2}-\d{2}-\d{4}"])]
    public function getActivities(
        EntityManagerInterface $entityManager,
        $date = null,
    ): JsonResponse {
        // Formatear la fecha si se proporciona
        $formattedDate = null;
        if ($date) {
            $dateTime = \DateTime::createFromFormat('d-m-Y', $date);
            if (!$dateTime) {
                return $this->json(['error' => 'Invalid date format']);
            }
            $formattedDate = $dateTime->format('Y-m-d');
        }

        // Obtener la lista de actividades con toda la información
        $activities = $entityManager->getRepository(Activity::class)->getActivitiesWithDetails($formattedDate);

        // Formatear la respuesta
        $formattedActivities = [];
        foreach ($activities as $activity) {
            $formattedActivity = [
                'id' => $activity->getId(),
                'activity_type' => $activity->getActivityType(),
                'date_start' => $activity->getDateStart() ? $activity->getDateStart()->format('Y-m-d H:i:s') : null,
                'date_end' => $activity->getDateEnd() ? $activity->getDateEnd()->format('Y-m-d H:i:s') : null,
                'monitors' => [],
            ];

            foreach ($activity->getMonitors() as $monitor) {
                $formattedActivity['monitors'][] = [
                    'id' => $monitor->getId(),
                    'name' => $monitor->getName(),
                    'email' => $monitor->getEmail(),
                    'phone' => $monitor->getPhone(),
                    'photo' => $monitor->getPhoto(),
                ];
            }
            $formattedActivities[] = $formattedActivity;
        }


        // Devolver la lista de actividades en formato JSON
        return $this->json($activities);
    }

    #[Route('/activities/{activity_type_id}/{date_start}/{monitors}', name: 'create_activity', methods: ['POST'])]
    public function createActivity(
        $activity_type_id,
        $date_start,
        $monitors,
        EntityManagerInterface $entityManager,
        
    ): JsonResponse {
        
        // Validar el formato de la fecha y hora de inicio
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date_start);
        if (!$dateTime || $dateTime->format('Y-m-d H:i:s') !== $date_start) {
            return $this->json(['error' => 'Invalid date format for date_start'], 400);
        }

        // Validar que la fecha sea la correcta
        $allowedStartTimes = ['09:00', '13:30', '17:30'];
        $providedStartTime = $dateTime->format('H:i');
        //tener en quenta que date time ya es la fecha formateada a Y-m-d H:i:s

        if (!in_array($providedStartTime, $allowedStartTimes)) {
            return $this->json(['error' => 'Invalid start time'], 400);
        }

        // Obtener el tipo de actividad
        $activityType = $entityManager->getRepository(ActivityType::class)->find($activity_type_id);

        if (!$activityType) {
            return $this->json(['error' => 'Invalid activity type'], 400);
        }

        // Validar los monitores requeridos
        $monitorsArray = explode(',', $monitors);
        $numberMonitors = $activityType->getNumberMonitors();
        if(count($monitorsArray) != $numberMonitors){
            return $this->json(['error' => 'Invalid number of monitors'], 400);
        }

        // Crear la nueva actividad
        $newActivity = new Activity();
        $newActivity->setActivityType($activityType);
        $newActivity->setDateStart(new \DateTime($date_start));

        // Calcular la fecha final sumando 90 minutos
        $dateEnd = clone $dateTime;
        $dateEnd->add(new \DateInterval('PT90M'));
        $newActivity->setDateEnd($dateEnd);

        // Persistir la nueva actividad en la base de datos
        $entityManager->persist($newActivity);
        $entityManager->flush();

        // Asociar los monitores a la actividad
        
        foreach ($monitorsArray as $monitorId) {
            $monitor = $entityManager->getRepository(Monitor::class)->find($monitorId);
            if ($monitor) {
                $newActivity->addMonitor($monitor);
            }
        }

        // Persistir la asociación de monitores con la actividad
        $entityManager->flush();

        // Devolver la información de la nueva actividad en formato JSON
        $responseData = [
            'id' => $newActivity->getId(),
            'activity_type' => $activityType->toArray(),
            'date_start' => $newActivity->getDateStart()->format('Y-m-d H:i:s'),
            'date_end' => $newActivity->getDateEnd()->format('Y-m-d H:i:s'),
            'monitors' => $monitorsArray,
        ];

        return $this->json($responseData);
    }

    #[Route('/activities/{id}/{activity_type_id}/{monitors}', name: 'edit_activity', methods: ['PUT'])]
    public function editActivity(
        $id,
        $activity_type_id,
        $monitors,
        EntityManagerInterface $entityManager,
        
    ): JsonResponse {
        // Obtener la actividad existente por ID
        $existingActivity = $entityManager->getRepository(Activity::class)->find($id);

        if (!$existingActivity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        // Obtener el tipo de actividad
        $activityType = $entityManager->getRepository(ActivityType::class)->find($activity_type_id);

        if (!$activityType) {
            return $this->json(['error' => 'Invalid activity type'], 400);
        }

        // Validar los monitores requeridos
        $monitorsArray = explode(',', $monitors);
        $numberMonitors = $activityType->getNumberMonitors();
        if (count($monitorsArray) != $numberMonitors) {
            return $this->json(['error' => 'Invalid number of monitors'], 400);
        }

        // Actualizar la actividad con los nuevos datos
        $existingActivity->setActivityType($activityType);

        // Borrar monitores existentes y asociar los nuevos monitores
        $existingActivity->clearMonitors();
        foreach ($monitorsArray as $monitorId) {
            $monitor = $entityManager->getRepository(Monitor::class)->find($monitorId);
            if ($monitor) {
                $existingActivity->addMonitor($monitor);
            }
        }

        // Persistir los cambios en la base de datos
        $entityManager->flush();

        // Devolver la información de la actividad actualizada en formato JSON
        $responseData = [
            'id' => $existingActivity->getId(),
            'activity_type' => $activityType->toArray(),
            'date_start' => $existingActivity->getDateStart()->format('Y-m-d H:i:s'),
            'date_end' => $existingActivity->getDateEnd()->format('Y-m-d H:i:s'),
            'monitors' => $monitorsArray,
        ];

        return $this->json($responseData);
    }

    #[Route('/activities/{id}', name: 'delete_activity', methods: ['DELETE'])]
    public function deleteActivity(
        $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Obtener la actividad existente por ID
        $existingActivity = $entityManager->getRepository(Activity::class)->find($id);

        if (!$existingActivity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        // Obtener los monitores asociados y eliminar las asociaciones
        $monitors = $existingActivity->getMonitors();

        foreach ($monitors as $monitor) {
            $existingActivity->removeMonitor($monitor);
        }

        // Eliminar la actividad de la base de datos
        $entityManager->remove($existingActivity);
        $entityManager->flush();

        // Devolver una respuesta de éxito en formato JSON
        // de la base de datos se elimina la informacion de la tabla activity_monitor ya que en la
        // añadí esta parte a la parte de la variable donde se encuentran
        // los monitores:  cascade: ['remove']    esto es algo muy util
        return $this->json(['message' => 'Activity deleted successfully']);
    }


}

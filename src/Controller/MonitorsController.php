<?php

namespace App\Controller;

use App\Entity\Monitor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MonitorsController extends AbstractController
{
    #[Route('/monitors', name: 'get_all_monitors', methods: ['GET'])]
    public function getAll(EntityManagerInterface $entityManager): JsonResponse
    {
        $monitors = $entityManager->getRepository(Monitor::class)->findAll();
        $data = [];

        foreach ($monitors as $monitor) {
            $data[] = [
                'id' => $monitor->getId(),
                'name' => $monitor->getName(),
                'email' => $monitor->getEmail(),
                'phone' => $monitor->getPhone(),
                'photo' => $monitor->getPhoto(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/monitors/create/{name}/{email}/{phone}/{photo}', name: 'create_monitor', methods: ['POST'])]
    public function createMonitor(
        $name,
        $email,
        $phone,
        $photo,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        Request $request,
        LoggerInterface $logger,
    ): JsonResponse {
        // Validar los campos

        $logger->debug('Request: '.$request->getContent());
        $monitor = new Monitor();
        $monitor
            ->setName($name)
            ->setEmail($email)
            ->setPhone($phone)
            ->setPhoto($photo);

        $errors = $validator->validate($monitor);

        if (count($errors) > 0) {
            $logger->error('Errores: '.$errors);
            return $this->json($errors, 400);
        }

        // Crear una nueva instancia de Monitor
        $newMonitor = new Monitor();
        $newMonitor
            ->setName($name)
            ->setEmail($email)
            ->setPhone($phone)
            ->setPhoto($photo);

        // Persistir el nuevo monitor en la base de datos
        $entityManager->persist($newMonitor);
        $entityManager->flush();

        // Devolver la información del nuevo monitor en formato JSON
        $responseData = [
            'id' => $newMonitor->getId(),
            'name' => $newMonitor->getName(),
            'email' => $newMonitor->getEmail(),
            'phone' => $newMonitor->getPhone(),
            'photo' => $newMonitor->getPhoto(),
        ];

        return $this->json($responseData);
    }


    #[Route('/monitors/edit/{id}/{name}/{email}/{phone}/{photo}', name: 'edit_monitor', methods: ['PUT'])]
    public function editMonitor(
        $id,
        $name,
        $email,
        $phone,
        $photo,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        Request $request,
    ): JsonResponse {
        // Validar datos entrantes

        $logger->debug('Request: '.$request->getContent());
        $monitor = new Monitor();
        $monitor
            ->setName($name)
            ->setEmail($email)
            ->setPhone($phone)
            ->setPhoto($photo);

        $errors = $validator->validate($monitor);

        if (count($errors) > 0) {
            $logger->error('Errores: '.$errors);
            return $this->json($errors, 400);
        }


        // Obtener el monitor existente por ID
        $existingMonitor = $entityManager->getRepository(Monitor::class)->find($id);

        // Verificar si el monitor existe
        if (!$existingMonitor) {
            return $this->json(['error' => 'Monitor not found']);
        }

        // Actualizar los campos del monitor existente
        $existingMonitor
            ->setName($name ?? $existingMonitor->getName())
            ->setEmail($email ?? $existingMonitor->getEmail())
            ->setPhone($phone ?? $existingMonitor->getPhone())
            ->setPhoto($photo ?? $existingMonitor->getPhoto());


        // Persistir los cambios en la base de datos
        $entityManager->flush();

        // Devolver la información del monitor editado en formato JSON
        $responseData = [
            'id' => $existingMonitor->getId(),
            'name' => $existingMonitor->getName(),
            'email' => $existingMonitor->getEmail(),
            'phone' => $existingMonitor->getPhone(),
            'photo' => $existingMonitor->getPhoto(),
        ];

        return $this->json($responseData);
    }

    #[Route('/monitors/delete/{id}', name: 'delete_monitor', methods: ['DELETE'])]
    public function deleteMonitor(
        $id,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        // Obtener el monitor por ID
        $monitor = $entityManager->getRepository(Monitor::class)->find($id);

        // Verificar si el monitor existe
        if (!$monitor) {
            return $this->json(['error' => 'Monitor not found']);
        }

        // Eliminar el monitor
        $entityManager->remove($monitor);
        $entityManager->flush();

        // Devolver respuesta JSON indicando éxito
        return $this->json(['message' => 'Monitor deleted successfully']);
    }
}

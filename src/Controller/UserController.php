<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_user_list')]
    #[IsGranted('ROLE_ADMIN')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user/userList.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/restrict', name: 'admin_user_restrict', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restrictUser(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $user = $userRepository->find($id);

        if ($this->isCsrfTokenValid('restrict' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsRestricted(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_user_list');
    }

    #[Route('/account', name: 'app_user_account')]
    public function account(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        return $this->render('user/account.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/account/edit', name: 'app_user_account_edit')]
    public function editAccount(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $form = $this->createForm(UserProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle the profile picture upload
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $newFilename = uniqid().'.'.$profilePictureFile->guessExtension();
                try {
                    $profilePictureFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }
                $user->setProfilePicture($newFilename);
            }

            // Handle the password update
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_user_account');
        }

        return $this->render('user/editAccount.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
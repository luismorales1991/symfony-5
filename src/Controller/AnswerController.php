<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class AnswerController extends AbstractController
{
    #[Route("/answers/{id}/vote", methods:"POST", name:"answer_vote")]
    #[IsGranted("IS_AUTHENTICATED_REMEMBERED")]
    public function answerVote(Answer $answer, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);
        
        $direction = $data['direction'] ?? 'up';
        $logger->info('{user} is voting on answer {answer}!', [
            'user' => $this->getUser()->getUserIdentifier(),
            'answer' => $answer->getId(),
        ]);

        // use real logic here to save this to the database
        if ($direction === 'up') {
            $logger->info('Voting up!');
            $answer->setVotes($answer->getVotes() + 1);
            $currentVoteCount = rand(7, 100);
        } else {
            $logger->info('Voting down!');
            $answer->setVotes($answer->getVotes() - 1);
        }

        $entityManager->flush();

        return $this->json(['votes' => $answer->getVotes()]);
    }

    #[Route("/answers/popular", name:"app_popular_answers")]
    public function popularAnswers(AnswerRepository $answerRepository, Request $request)
    {
        $answers = $answerRepository->findMostPopular(
            $request->query->get('q')
        );
        return $this->render('answer/popularAnswers.html.twig', [
            'answers' => $answers
        ]);
    }
}

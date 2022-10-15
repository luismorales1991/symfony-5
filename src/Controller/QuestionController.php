<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\QuestionRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AnswerRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class QuestionController extends AbstractController
{
    private $logger;
    private $isDebug;

    public function __construct(LoggerInterface $logger, bool $isDebug)
    {
        $this->logger = $logger;
        $this->isDebug = $isDebug;
    }

    #[Route("/{page<\d+>}", name:"app_homepage")]
    public function homepage(QuestionRepository $repository, int $page = 1)
    {
        $queryBuilder = $repository->createAskedOrderedByNewestQueryBuilder();

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta->setMaxPerPage(5);
        $pagerfanta->setCurrentPage($page);
        
        return $this->render('question/homepage.html.twig', [
            'pager' => $pagerfanta,
        ]);
    }

    
    #[Route("/questions/new")]
    public function new(EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return new Response('Sounds like a GREAT feature for V2!');
    }

    #[Route("/questions/{slug}", name:"app_question_show")]
    public function show(Question $question, AnswerRepository $answerRepository)
    {
        if ($this->isDebug) {
            $this->logger->info('We are in debug mode!');
        }

        $answers = $question->getAnswers();

        return $this->render('question/show.html.twig', [
            'question' => $question
        ]);
    }

    
    #[Route("/questions/{slug}/vote", name:"app_question_vote", methods:"POST")]
    public function questionVote(Question $question, Request $request, EntityManagerInterface $entityManager)
    {
        $direction = $request->request->get('direction');
        if ($direction === 'up') {
            $question->upVote();
        } elseif ($direction === 'down') {
            $question->downVote();
        }

        $entityManager->flush();
        
        return $this->redirectToRoute('app_question_show', [
            'slug' => $question->getSlug()
        ]);
    }
    
    #[Route("/questions/edit/{slug}", name:"app_question_edit")]
    public function edit(Question $question)
    {
        $this->denyAccessUnlessGranted('EDIT',$question);
        if ($question->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not the owner!');
        }
        return $this->render('question/edit.html.twig', [
            'question' => $question,
        ]);
    }
}

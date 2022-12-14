<?php

namespace App\Controller\Admin;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\QuestionRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    private QuestionRepository $questionRepository;
    private ChartBuilderInterface $chartBuilder;
    
    public function __construct(
        QuestionRepository $questionRepository,
        ChartBuilderInterface $chartBuilder
        )
    {
        $this->questionRepository = $questionRepository;
        $this->chartBuilder = $chartBuilder;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $latestQuestions = $this->questionRepository->findAll();
        $topVoted = $this->questionRepository->findBy(array(),["votes" => "DESC"]);

        return $this->render('admin/index.html.twig',[
            'latestQuestions' => $latestQuestions,
            'topVoted' => $topVoted,
            'chart' => $this->createChart()
        ]);

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Cauldron Overflow Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Home', 'fas fa-home', $this->generateUrl("app_homepage"));
        yield MenuItem::linkToDashboard('Dashboard', 'fas fa-tachometer-alt');
        yield MenuItem::section('Content');
        yield MenuItem::subMenu('Questions', 'fa fa-question-circle')
            ->setSubItems([
                MenuItem::linkToCrud('All', 'fa fa-list', Question::class)
                    ->setController(QuestionCrudController::class)
                    ->setPermission('ROLE_MODERATOR'),
                MenuItem::linkToCrud('Pending Verification', 'fa fa-warning', Question::class)
                    ->setPermission('ROLE_MODERATOR')
                    ->setController(QuestionPendingApprovalCrudController::class),
            ]);
        yield MenuItem::linkToCrud('Answers', 'fas fa-comments', Answer::class);
        yield MenuItem::linkToCrud('Tags', 'fas fa-folder', Tag::class);
        yield MenuItem::section("mamadas.com");
        yield MenuItem::linkToUrl('StackOverflow', 'fab fa-stack-overflow', 'https://stackoverflow.com')
            ->setLinkTarget('_blank');
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        if(!$user instanceof User) {
            throw new \Exception("Wrong User");
        }

        return parent::configureUserMenu($user)
            ->setAvatarUrl($user->getAvatarUri());
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addWebpackEncoreEntry('admin');
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->setDefaultSort([
                'id' => 'ASC',
            ])
            ->overrideTemplate('crud/field/id', 'admin/field/id_with_icon.html.twig');
    }

    private function createChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'My First dataset',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                   'suggestedMin' => 0,
                   'suggestedMax' => 100,
                ],
            ],
        ]);
        return $chart;
    }
}

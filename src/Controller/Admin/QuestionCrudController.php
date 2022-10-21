<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Question;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Component\HttpFoundation\RequestStack;

#[IsGranted('ROLE_MODERATOR')]
class QuestionCrudController extends AbstractCrudController
{
    private AdminUrlGenerator $adminUrlGenerator;
    private RequestStack $requestStack;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, RequestStack $requestStack)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return Question::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add('createdAt')
            ->add('votes')
            ->add('name');
    }

    public function configureActions(Actions $actions): Actions
    {   
        $viewAction = Action::new('view')
            ->linkToUrl(function(Question $question) {
                return $this->generateUrl('app_question_show', [
                    'slug' => $question->getSlug(),
                ]);
            })
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-eye')
            ->setLabel('View on site');

        $exportAction = Action::new('export')
            ->linkToUrl(function () {
                $request = $this->requestStack->getCurrentRequest();
                return $this->adminUrlGenerator->setAll($request->query->all())
                    ->setAction('export')
                    ->generateUrl();
            })
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();
        
        $approveAction = Action::new('approve')
            ->setTemplatePath('admin/approve_action.html.twig')
            ->linkToCrudAction('approve')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check-circle')
            ->displayAsButton()
            ->displayIf(static function (Question $question): bool {
                return !$question->isIsVerified();
            });

        return parent::configureActions($actions)
            // ERROR: Call to a member function getBaseUrl() on null;
          /* ->update(Crud::PAGE_INDEX, Action::DELETE, static function(Action $action) {
                $action->displayIf(static function (Question $question) {
                    return !$question->isIsVerified();
                });
            }) */
            ->setPermission(Action::INDEX, 'ROLE_MODERATOR')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->add(Crud::PAGE_DETAIL, $viewAction)
            ->add(Crud::PAGE_INDEX, $viewAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->reorder(Crud::PAGE_DETAIL, [
                'approve',
                'view',
                Action::EDIT,
                Action::INDEX,
                Action::DELETE,
            ])
            ->update(Crud::PAGE_DETAIL, Action::EDIT, static function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, static function (Action $action) {
                return $action->setIcon('fa fa-list');
            });
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getIsApproved()) {
            throw new \Exception('Deleting approved questions is forbidden!');
        }
        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            FormField::addTab('Basic Data')
                ->collapsible(),
            Field::new('name')
                ->setColumns(5),
            Field::new('slug')
                ->hideOnIndex()
                ->setColumns(5)
                ->setFormTypeOption(
                    'disabled',
                    $pageName !== Crud::PAGE_NEW
                ),
            VotesField::new('votes', 'Total Votes'),
            Field::new('createdAt')
                ->hideOnForm(),
            TextareaField::new('question')
                ->hideOnIndex()
                ->setFormTypeOptions([
                    'row_attr' => [
                        'data-controller' => 'snarkdown',
                    ],
                    'attr' => [
                        'data-snarkdown-target' => 'input',
                        'data-action' => 'snarkdown#render',
                    ],
                ]),
            FormField::addTab('Details')
                ->collapsible()
                ->setIcon('fa fa-info')
                ->setHelp('Additional Details'),
            AssociationField::new('owner')
                ->autocomplete(),
            AssociationField::new('answers')
                ->autocomplete()
                ->setFormTypeOption('by_reference', false),
            AssociationField::new('updatedBy')
                ->onlyOnDetail(),
            BooleanField::new("isVerified")->renderAsSwitch(false)
        ];
    }

    public function approve(AdminContext $adminContext, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $question = $adminContext->getEntity()->getInstance();
        
        if (!$question instanceof Question) {
            throw new \LogicException('Entity is missing or not a Question');
        }
        $question->setIsVerified(true);

        $entityManager->flush();

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($question->getId())
            ->generateUrl();
        return $this->redirect($targetUrl);
    }

    
    public function export(AdminContext $context,  CsvExporter $csvExporter)
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        return $csvExporter->createResponseFromQueryBuilder($queryBuilder, $fields, 'questions.csv');
    }
}

<?php namespace App\Controller\Admin;

use App\Entity\Question;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

// Ese wey hizo otro programa así que no son las preguntas por aprovar
// Sino preguntas con votos mayor a 0

class QuestionPendingApprovalCrudController extends QuestionCrudController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.isVerified = :numbah')
            ->setParameter('numbah', 0);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'Questions pending verification')
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Question $question) {
                return sprintf('#%s %s', $question->getId(), $question->getName());
            })
            ->setHelp(Crud::PAGE_INDEX, 'Questions are not published to users until verification by a moderator')
            ->showEntityActionsInlined();
    }
}

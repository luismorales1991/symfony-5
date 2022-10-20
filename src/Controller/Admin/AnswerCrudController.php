<?php

namespace App\Controller\Admin;

use App\Entity\Answer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use App\EasyAdmin\VotesField;

class AnswerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Answer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            TextareaField::new('content'),
            VotesField::new('votes', 'Total Votes'),
            AssociationField::new('question')
                ->autocomplete()
                ->setCrudController(QuestionCrudController::class),
            Field::new("username")
                ->hideOnForm(),
            Field::new('createdAt')
                ->hideOnForm(),
            Field::new('updatedAt')
                ->onlyOnDetail()
        ];
    }
}

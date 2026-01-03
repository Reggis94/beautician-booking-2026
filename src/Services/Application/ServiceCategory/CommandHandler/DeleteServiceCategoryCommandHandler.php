<?php

namespace App\Services\Application\ServiceCategory\CommandHandler;

use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;
use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;

final class DeleteServiceCategoryCommandHandler{
    public function __construct(private ServiceCategoryDaoInterface $serviceCategoryDao){

    }
    public function __invoke(DeleteServiceCategoryCommand $command){
        if($this->serviceCategoryDao->doesBelongToPro((int) $command->id, (int) $command->proId)){
            $this->serviceCategoryDao->delete($command->id);
        }else{
            throw new \DomainException('The service category does not belong to pro');
        }
    }
}
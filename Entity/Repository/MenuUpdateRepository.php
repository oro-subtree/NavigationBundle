<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

class MenuUpdateRepository extends EntityRepository
{
    /**
     * @param string $menu
     * @param Organization|null $organization
     * @param BusinessUnit|null $businessUnit
     * @param User|null $user
     *
     * @return MenuUpdate[]
     */
    public function getMenuUpdates(
        $menu,
        Organization $organization = null,
        BusinessUnit $businessUnit = null,
        User $user = null
    ) {
        $qb = $this->createQueryBuilder('mu');
        $exprs = [
            $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_GLOBAL),
                $qb->expr()->isNull('mu.ownerId')
            )
        ];
        if ($organization !== null) {
            $exprs[] = $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_ORGANIZATION),
                $qb->expr()->eq('mu.ownerId', $organization->getId())
            );
        }
        if ($businessUnit !== null) {
            $exprs[] = $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_BUSINESS_UNIT),
                $qb->expr()->eq('mu.ownerId', $businessUnit->getId())
            );
        }
        if ($user !== null) {
            $exprs[] = $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_USER),
                $qb->expr()->eq('mu.ownerId', $user->getId())
            );
        }

        $expr = call_user_func_array([$qb->expr(), 'orX'], $exprs);
        $qb->where($qb->expr()->andX(
            $qb->expr()->eq('mu.menu', ':menu'),
            $qb->expr()->orX($expr)
        ));

        return $qb
            ->setParameter('menu', $menu)
            ->orderBy('mu.ownershipType', 'ASC')
            ->addOrderBy('mu.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

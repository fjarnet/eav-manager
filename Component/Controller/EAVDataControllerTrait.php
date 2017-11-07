<?php
/*
 *    CleverAge/EAVManager
 *    Copyright (C) 2015-2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\EAVManager\Component\Controller;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use CleverAge\EAVManager\UserBundle\Entity\User;

/**
 * @method Registry getDoctrine
 * @method User getUser
 * @method addFlash($key, $message)
 *
 * @property \Symfony\Component\DependencyInjection\ContainerInterface $container
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
trait EAVDataControllerTrait
{
    /** @var FamilyInterface */
    protected $family;

    /**
     * @param string $familyCode
     *
     * @return FamilyInterface
     *
     * @throws \Exception
     */
    protected function getFamily($familyCode): FamilyInterface
    {
        return $this->container->get('sidus_eav_model.family.registry')->getFamily($familyCode);
    }

    /**
     * @param FamilyInterface $family
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    protected function setFamily(FamilyInterface $family)
    {
        $this->family = $family;
        $this->container->get('router')->getContext()->setParameter('familyCode', $family->getCode());
    }

    /**
     * @param                      $id
     * @param FamilyInterface|null $family
     *
     * @throws \Exception
     *
     * @return DataInterface
     */
    protected function getData($id, FamilyInterface $family = null): DataInterface
    {
        if ($id instanceof DataInterface) {
            $data = $id;
        } else {
            $dataClass = $this->container->getParameter('sidus_eav_model.entity.data.class');
            if ($family) {
                $dataClass = $family->getDataClass();
            }
            /** @var DataInterface $data */
            $data = $this->getDoctrine()->getRepository($dataClass)->find($id);

            if (!$data) {
                throw new NotFoundHttpException("No data found with id : {$id}");
            }
        }
        if (!$family) {
            $family = $data->getFamily();
        }
        $this->initDataFamily($data, $family);

        return $data;
    }

    /**
     * @param FamilyInterface $family
     * @param DataInterface   $data
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    protected function initDataFamily(DataInterface $data, FamilyInterface $family = null)
    {
        if (!$family) {
            $family = $data->getFamily();
        } elseif ($family->getCode() !== $data->getFamilyCode()) {
            throw new \UnexpectedValueException(
                "Data family '{$data->getFamilyCode()}'' not matching admin family {$family->getCode()}"
            );
        }
        $this->setFamily($family);
    }
}

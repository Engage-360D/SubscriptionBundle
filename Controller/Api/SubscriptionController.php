<?php

namespace Engage360d\Bundle\SubscriptionBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class SubscriptionController extends Controller
{
    /**
     * Ресурс для поиска по всем проиндексированным сущностям.
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Подписка контакта в список рассылки по умолчанию",
     *  parameters={
     *      {"name"="subscription_contact[email]", "dataType"="string", "required"=true, "description"="Email контакта"},
     *      {"name"="subscription_contact[phone]", "dataType"="string", "required"=false, "description"="Мобильный телефон контакта"},
     *      {"name"="subscription_contact[name]", "dataType"="string", "required"=false, "description"="Имя контакта"}
     *  }
     * )
     */
    public function postSubscriptionContactsAction()
    {
        $unisender = $this->get('engage360d_subscription.unisender');
        $defaultListId = $this->container->getParameter('engage360d_subscription.default_list_id');

        $form = $this->container->get('form.factory')->createNamedBuilder('subscription_contact', 'form')
            ->add('email', 'email', array(
                'constraints' => array(
                    new NotBlank(),
                    new Email()
                )
            ))
            ->add('phone', 'text', array(
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '/^[0-9]{10}$/',
                        'message' => 'This value is not a valid mobile phone number.'
                    ))
                )
            ))
            ->add('name', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'max' => 128,
                        'maxMessage' => 'Name is too log. Max length is 128 characters.'
                    ))
                )
            ))
            ->getForm();

        $form->handleRequest($this->getRequest());

        if (!$form->isValid()) {
            return new Response($this->get('jms_serializer')->serialize($form, 'json'), 200, array(
                'Content-Type' => 'application/json'
            ));
        }

        $contact = array(
            'fields' => $form->getData(),
            'list_ids' => $defaultListId,
            'double_optin' => 1,
            'confirm_ip' => $this->getRequest()->getClientIp()
        );

        $contact['fields']['Name'] = $contact['fields']['name'];
        unset($contact['fields']['name']);

        $result = json_decode($unisender->subscribe($contact));

        if (isset($result->error)) {
            return new JsonResponse(array(
                'errors' => array(
                    $result->error
                ),
                'children' => array(
                    'email' => array(),
                )
            ));
        }

        return new JsonResponse(array(
            'id' => $result->result->person_id,
            'fields' => $contact['fields']
        ));
    }
}

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
     *      {"name"="data[email]", "dataType"="string", "required"=true, "description"="Email контакта"},
     *      {"name"="data[phone]", "dataType"="string", "required"=false, "description"="Мобильный телефон контакта"},
     *      {"name"="data[name]", "dataType"="string", "required"=false, "description"="Имя контакта"}
     *  }
     * )
     */
    public function postSubscriptionContactsAction()
    {
        $unisender = $this->get('engage360d_subscription.unisender');
        $defaultListId = $this->container->getParameter('engage360d_subscription.default_list_id');

        $form = $this->container->get('form.factory')->createNamedBuilder('data', 'form')
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
            // TODO json-api
            return new Response($this->get('jms_serializer')->serialize($form, 'json'), 200, array(
                'Content-Type' => 'application/json'
            ));
        }

        $fields = $form->getData();

        $exists = json_decode($unisender->exportContacts(array(
            'list_id' => $defaultListId,
            'email' => $fields['email'],
        )), true)['result']['data'];

        if (count($exists) > 0) {
            // TODO json-api
            return new Response(json_encode(array(
                'errors' => array(
                    'Данный email уже находится в списке рассылки'
                )
            )), 200, array(
                'Content-Type' => 'application/json'
            ));
        }

        $contact = array(
            'fields' => $fields,
            'list_ids' => $defaultListId,
            'double_optin' => 1,
            'confirm_ip' => $this->getRequest()->getClientIp()
        );

        $contact['fields']['Name'] = $contact['fields']['name'];
        unset($contact['fields']['name']);

        $result = json_decode($unisender->subscribe($contact));

        if (isset($result->error)) {
            // TODO json-api
            return new JsonResponse(array(
                'errors' => array(
                    $result->error
                ),
                'children' => array(
                    'email' => array(),
                )
            ));
        }

        $data = $contact['fields'];
        $data['id'] = (string) $result->result->person_id;

        return new JsonResponse(array(
            'data' => $data,
        ));
    }
}

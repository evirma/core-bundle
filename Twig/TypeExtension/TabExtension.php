<?php

namespace Evirma\Bundle\CoreBundle\Twig\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TabExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes()
    {
        return [FormType::class];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['tab']);
        $resolver->setDefaults([
            'tab' => [
                'namespace' => null,
                'name' => null,
                'label' => null,
                'pos' => 99
            ]
        ]);
    }


    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $namespace = $options['tab']['namespace'];
        if (null === $namespace) {
            return;
        }

        $tabName = isset($options['tab']['name']) ? $options['tab']['name'] : $namespace;
        $tabLabel = isset($options['tab']['label']) ? $options['tab']['label'] : $namespace;
        $tabPos = isset($options['tab']['pos']) ? $options['tab']['pos'] : 99;

        $root = $this->getRootView($view);
        if (!isset($root->vars['tabs'][$namespace][$tabName])) {
            $root->vars['tabs'][$namespace][$tabName] =
                [
                    'name' => $tabName,
                    'label' => $tabLabel,
                    'pos'   => $tabPos,
                ];
        }

        $item = [
            'name' => $form->getName(),
            'pos' => isset($view->vars['attr']['pos']) ? $view->vars['attr']['pos'] : 99
        ];

        if (!isset($root->vars['tabs'][$namespace][$tabName]['elements'])) {
            $root->vars['tabs'][$namespace][$tabName]['elements'] = [$item];
        } else {
            $root->vars['tabs'][$namespace][$tabName]['elements'][] = $item;
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $root = $this->getRootView($view);
        if (isset($root->vars['tabs'])) {
            foreach ($root->vars['tabs'] as $namespace => &$tabs) {
                if (count($tabs) > 1) {
                    uasort(
                        $tabs,
                        function ($a, $b) {
                            return $a['pos'] <=> $b['pos'];
                        }
                    );
                }

                foreach ($tabs as &$tab) {
                    uasort($tab['elements'], function ($a, $b) {
                        return $a['pos'] <=> $b['pos'];
                    });
                }
            }
        }

        parent::finishView($view, $form, $options);
    }

    public function getRootView(FormView $view)
    {
        $root = $view->parent;
        while (null !== $root) {
            if (is_null($root->parent)) {
                break;
            }
            $root = $root->parent;
        }

        return $root;
    }
}
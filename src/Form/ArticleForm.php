<?php
/**
 * Log in form.
 *
 * @author EPI <epi@uj.edu.pl>
 * @link http://epi.uj.edu.pl
 * @copyright 2015 EPI
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CommentForm.
 *
 * @category Epi
 * @package Form
 * @extends AbstractType
 * @use Symfony\Component\Form\AbstractType
 * @use Symfony\Component\Form\FormBuilderInterface
 * @use Symfony\Component\OptionsResolver\OptionsResolverInterface
 * @use Symfony\Component\Validator\Constraints as Assert
 */
class ArticleForm extends AbstractType
{

    private $categories;
    public function __construct($categories)
    {
        $this->categories = $categories;
    }
    /**
     * Form builder.
     *
     * @access public
     * @param FormBuilderInterface $builder
     * @param array $options
     *
     * @return FormBuilderInterface
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $categories = $this->categories;

        return $builder
            ->add(
                'title', 'text',
                array(
                    'label' => 'Asd',
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(array('min' => 5))
                    ),
                    'attr' => array(
                        'class' => 'form-control'
                    )
                )
            )
            ->add(
                'content', 'textarea',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(array('min' => 5))
                    ),
                    'attr' => array(
                        'class' => 'form-control'
                    )
                )
            )
            ->add(
                'category_id', 'choice',
                array(
                    'choices' => $categories,
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                    'attr' => array(
                        'class' => 'form-control'
                    )
                )
            );
    }

    /**
     * Gets form name.
     *
     * @access public
     *
     * @return string
     */
    public function getName()
    {
        return 'articleForm';
    }
}
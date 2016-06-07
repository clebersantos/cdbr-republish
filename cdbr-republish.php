<?php 

/*
Plugin Name: Cultura Digital Republish
Plugin URI: http:culturadigital.br
Description: Republica os posts e taxonomias no blog selecionado
Author: Cleber Santos
Stable tag: 0.1
Author URI: http://culturadigital.br/members/clebersantos

    RePost is released under the GNU General Public License (GPL)
    http://www.gnu.org/licenses/gpl.txt

*/ 

class CDbrRepublish
{	
	// ATRIBUTOS ////////////////////////////////////////////////////////////////////////////////////

	// METODOS //////////////////////////////////////////////////////////////////////////////////////
	/************************************************************************************************
		Cria os valores padrão para a configuração do plugin.

		@name    install
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-11-18
		@updated 2014-11-18
	************************************************************************************************/
	function install()
	{
		
	}

	/************************************************************************************************
		Criar os Menus na ára administrativa.

		@name    menus
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-11-18
		@updated 2014-11-18
	************************************************************************************************/
	function menus()
	{
		// Menus secundários
		// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page('options-general.php', __('Cultura Digital Republish', 'cdbr_republish'), 'Republicar Posts', 'edit_posts', 'cdbr_republish', array(&$this, 'config'));
	}

	/************************************************************************************************
		Configurações do plugin.

		@name    config
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-11-18
		@updated 2014-11-18
	************************************************************************************************/
	function config()
	{
		// Inicializa as variáveis necessárias
		global $wpdb;

		$options = array();

		// checar privilegios 
		// if( !current_user_can( 'user_admin_menu' ) )
		// 	wp_die( "Acesso negado!", "Erro" );

		// Salvando as opções
		if( isset($_POST['cdbr_republish_save'] ) )
		{
			// temporário para não haver erro na reunião de acervos
			$options['id_blog_main'] = $this->get_blog_id_by_path( '/acervos/' );
			// $options['id_blog_main'] 			 = isset( $_POST['republish_id_blog_main'] ) ? $_POST['republish_id_blog_main'] : '' ;
			$options['synchronize_categories'] 	 = isset( $_POST['synchronize_categories'] ) ? $_POST['synchronize_categories'] : '' ;
			$options['republish_enable'] 		 = isset( $_POST['republish_enable'] ) ? (boolean) $_POST['republish_enable'] : '';
			
			// Salva no banco
			update_option('cdbr_republish_options', $options);
		}

		// Carregar as opções desse widget
		$options = get_option('cdbr_republish_options');
		
		$sussa 	 = isset( $_POST['sussa'] ) ? $_POST[ 'sussa' ] : "";
		
		?>
			<div class="wrap">
				<h2>Republicar Posts - Configurações</h2>
				<form action="" method="post">

					<?php if( '1' == $sussa ) : ?>
						<div id="message" class="updated below-h2">
							<p>Dados atualizados!</p>
						</div>
					<?php endif; ?>
					
					<input type="hidden" name="cdbr_republish_save" value="1" />
					<table class="form-table">
						<tbody>
							<?php if( is_multisite() ) : ?>
								<?php  if( empty( $options['id_blog_main'] ) ) $options['id_blog_main'] = $this->get_blog_id_by_path( '/acervos/' ); ?>
								<?php $blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} WHERE deleted = %d AND spam = %d ORDER BY blog_id", 0, 0 ) ); ?>
								<tr valign="top">
									<th scope="row"><label for="republish_id_blog_main">Blog onde os posts serão salvos:</label><br /></th>
									<td>
										<select id="republish_id_blog_main" name="republish_id_blog_main" disabled="disabled">
											<?php foreach( $blogs as $blog ) : ?>
												<?php if( $blog->blog_id != get_current_blog_id() ) : ?>
													<option value="<?php print $blog->blog_id; ?>" <?php if( $blog->blog_id == $options['id_blog_main'] ) print 'selected="selected"'; ?>><?php print "{$blog->domain}{$blog->path}"; ?></option>
												<?php endif; ?>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>

							<!-- 	<tr valign="top">
									<th scope="row">Sincronizar categorias</th> 
									<td>
										<label><input name="synchronize_categories" type="checkbox" id="synchronize_categories" value="1" <?php if( isset( $options['synchronize_categories'] ) && $options['synchronize_categories'] == 1 ) { echo "checked='checked'"; } ?> /> <strong>Ativar</strong></label><br />
									</td> 
								</tr> -->

								<tr valign="top">
									<th scope="row">Ativar</th> 
									<td>
										<label><input name="republish_enable" type="checkbox" id="republish_enable" value="1" <?php if( isset( $options['republish_enable'] ) && $options['republish_enable'] == 1 ) { echo "checked='checked'"; } ?> /> <strong>Ativar</strong></label><br />
									</td> 
								</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<p class="submit">
						<input type="hidden" name="sussa" value="1" />
						<button type="submit" class="button-primary"><?php _e('Save'); ?></button>
					</p>
				</form>
		
				<?php if( isset($_POST['cdbr_republish_synchronize'] ) )
						$this->save_categories_blog_main(); ?>

				<form action="" method="post">
							
					<?php if( '2' == $sussa ) : ?>
						<div id="message" class="updated below-h2">
							<p>Categorias sincronizadas com o blog principal!</p>
						</div>
					<?php endif; ?>
					
					<input type="hidden" name="cdbr_republish_synchronize" value="1" />
					

					<p class="submit">
						<input type="hidden" name="sussa" value="2" />
						<button type="submit" class="button-primary">Sincronizar categorias</button>
					</p>
					<p>Clique para sincronizar suas categorias com o blog informado selecionado.</p>
				</form>

			</div>
		<?php
	}


    /************************************************************************************************
		Salvar no blog atual as categorias do blog principal

		@name    save_categories_blog_main
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-12-01
		@updated 2014-12-01
		@param   void
		@return  void
	************************************************************************************************/
	function save_categories_blog_main( ) {

		// checar privilegios 
		// if( !current_user_can( 'user_admin_menu' ) )
		// 	wp_die( "Acesso negado!", "Erro" );

		// pega o id do blog principal
		$options = get_option('cdbr_republish_options');

		// if( !$options['synchronize_categories'] == 1 )
		// 	return;

		if( !$options['republish_enable'] == 1 )
			return;

	    if( empty( $options['id_blog_main'] ) ) 
	    	return;
	    
	    // variáveis necessárias
	    $current_blog_id = get_current_blog_id();
	    $term = array();

	    if( function_exists( 'switch_to_blog' ) ) switch_to_blog( $options['id_blog_main'] );

			# pega as categorias do blog principal
			$categories = get_categories( array('hierarchical' => true, 'hide_empty' => 0 ) );
			
			# pega os nomes das categorias do blog principal
			foreach( $categories as $c ) {
			
				$cat = get_category( $c );

				$parent = array();

				# se tiver parent
				if( !empty( $cat->parent ) ){
					
					# pega os dados da categoria parent
					$parent = get_category( $cat->parent );
				}

				$cats[] = array( 'name' => esc_html( $cat->name ), 'slug' => esc_html( $cat->slug ), 'description' => esc_html( $cat->category_description ), 'parent' => $parent );
			}

		if( function_exists( 'switch_to_blog' ) ) restore_current_blog();

		if( is_array( $cats ) && !empty( $cats ) ) {

			foreach( $cats as $t => $d ) {

				# se tiver um pai, pega o slug da categoria pai
				if( isset( $d['parent']->slug ) ) {
					
					$parent_slug = $d['parent']->slug;

					# busca no banco se existe uma categoria com esse slug
					$parent = get_term_by( 'slug', $parent_slug, 'category' );
					
					# se tiver um pai com o mesmo slug, adiciona o id do termo pai nessa categoria
					if( !empty( $parent ) ) {

						$d['parent'] = $parent->term_id;

					} else { 

						# se não tiver, inserir o pai no banco
						wp_insert_term( $d['parent']->name, 'category',  array('name' => $d['parent']->name, 'description' => $d['parent']->description, 'slug' => $d['parent']->slug ) );
							
						# pega categoria pai
						$parent = get_term_by( 'slug', $d['parent']->slug, 'category' );

						$d['parent'] = $parent->term_id;
					}
				} else {
					$d['parent'] = "";
				}

				# montando o array para gravar a categoria
				$args = array (
					'name' 			   => $d['name'],
					'description' 	   => $d['description'],
					'slug'	   		   => $d['slug'],
					'parent' 	   	   => $d['parent'],
					'taxonomy' 		   => 'category'
				);

				unset($term); // limpa o array

				# verifica se o termo já existe
				$term = get_term_by( 'slug', $d['slug'], 'category' );

				# se existir, atualizar
				if( !empty( $term ) ) {
					wp_update_term( $term->term_id, 'category', $args );
				
				} else {
					# registra um novo
					wp_insert_term( $d['name'] , 'category', $args );
				}

			}
		}

	}


    /************************************************************************************************
		Pega o id do blog pelo path

		@name    get_blog_id_by_path
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-11-18
		@updated 2014-11-18
		@param   string $path
		@return  int $blog_id
	************************************************************************************************/
	function get_blog_id_by_path( $path ) {

		global $wpdb;

    	$blog_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} WHERE path = '{$path}'" );
	
    	return $blog_id;
	}

	/************************************************************************************************
		Prepara o novo post

		@name    cdbr_republish_insert_post
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-11-18
		@updated 2014-11-18
		@param   int $post_id, object $post
		@return  void
	************************************************************************************************/
	function cdbr_republish_insert_post( $post_id, $post ) {

		# evita que o post seja salvo antes de concluir
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        	return;

	    if( defined( 'DOING_AJAX' ) && DOING_AJAX )
	        return;

	    # bloqueia auto-drafts e revisions
	    if( in_array( $post->post_status, array( 'auto-draft', 'inherit' ) ) )
	        return;

	    $options = get_option('cdbr_republish_options');

		if( !$options['republish_enable'] == 1 )
			return;

	    $blog_id_main = $options['id_blog_main'];

	    if( empty( $blog_id_main ) ) 
	    	return;

	    # carrega as variáveis
	    global $wpdb;

	    $cats = array();
	    $post_blog_id = $wpdb->blogid;

	    # pega as categorias do post atual
		$post->post_category = wp_get_post_categories( $post_id );
		
		# pega os nomes das categorias do post atual
		foreach( $post->post_category as $c ) {
			$cat = get_category( $c );
			$cats[] = array( 'name' => esc_html( $cat->name ), 'slug' => esc_html( $cat->slug ) );
		}

		# pega as tags do post atual e converte em string
		$post->tags_input = implode( ', ', wp_get_post_tags( $post_id, array('fields' => 'names') ) );

		# prepara o guid para ser o identificador desse post
		$post->guid = $post_blog_id . '.' . $post_id;

		# atributos para criar o permalink e thumbnail
		$global_meta = array();
		$global_meta['permalink'] = get_permalink( $post_id );
		$global_meta['blogid'] = $org_blog_id = $wpdb->blogid; # org_blog_id
		$thumb_id = get_post_meta( $post->ID, '_thumbnail_id', true );
		$thumb_size = 'thumbnail';
		$global_meta['thumbnail_html'] = wp_get_attachment_image( $thumb_id, $thumb_size );

		$format = get_post_format( $post_id );


		# meta para verificar se este post já foi salvo, garantir que um loop não acontecerá
	    // $termid = get_post_meta( $post_id, '_termid', true );
       		
   		# remove o action para não cair em um loop infinito
   		remove_action( 'save_post',  array(&$this, 'cdbr_republish_insert_post'), 9 );

		if( function_exists( 'switch_to_blog' ) ) switch_to_blog( $blog_id_main );

			if( is_array( $cats ) && !empty( $cats ) && $post->post_status == 'publish' ) {
				
				foreach( $cats as $t => $d ) {

					# verifica se o termo existe no blog pai
					$term = get_term_by( 'slug', $d['slug'], 'category' );

					
					# verificar pq o id da categoria está duplicando em todos os blogs o id está sendo o mesmo
					if( $term ) {
						$category_id[] = $term->term_id;
						continue;
					}

					# insere as categorias no blog principal
					// wp_insert_category( array('cat_name' => $d['name'], 'category_description' => $d['name'], 'category_nicename' => $d['slug'], 'category_parent' => '') );
					
					# pega o id das categorias que vão ser utilizadas no post
					 // $category_id[] = $wpdb->get_var( "SELECT term_id FROM " . $wpdb->get_blog_prefix( $blog_id_main  ) . "terms WHERE slug = '" . $d['slug'] . "'" );
				}
			}

			# verifica se esse post já existe no blog principal
			$global_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE guid IN (%s,%s)", $post->guid, esc_url( $post->guid ) ) );
			
			# se existir mas não é publicado, deletar!
			if( $post->post_status != 'publish' && is_object( $global_post ) ) {
				wp_delete_post( $global_post->ID );
			} else {

				//se existe entao altera
				if( $global_post->ID != '' ) {
					$post->ID = $global_post->ID; # editing an old post
					
					foreach( array_keys( $global_meta ) as $key )
						delete_post_meta( $global_post->ID, $key );

				} else { # se não, deleta o ID para criar um novo post
					unset( $post->ID ); # new post
				}
			}

			# Insere o novo post

			if( $post->post_status == 'publish' ) {
				$post->ping_status = 'closed';
				$post->comment_status = 'closed';

				/* Use the category ID in the post */
			    $post->post_category = $category_id;

			    // insere o post
				$p = wp_insert_post( $post );

				// salva o formato do post
				set_post_format( $p, $format );

				// salva informações do post
				foreach( $global_meta as $key => $value )
					if( $value )
						add_post_meta( $p, $key, $value );
			}

		if( function_exists( 'switch_to_blog' ) ) restore_current_blog();

		// update_post_meta( $post_id, '_termid', 'update' );

		add_action( 'save_post', array(&$this, 'cdbr_republish_insert_post'), 9, 2 );

	}


	// CONSTRUTOR ///////////////////////////////////////////////////////////////////////////////////
	/************************************************************************************************
		@name    CDbrRepublish
		@author  Cleber Santos <oclebersantos@gmailcom>
		@since   2014-11-18
		@updated 2014-11-18
		@return  void
	************************************************************************************************/
	function __construct()
	{

		// adicionando o menu
		add_action( 'admin_menu', array(&$this, 'menus'));
	
		add_action( 'save_post', array(&$this, 'cdbr_republish_insert_post'), 9, 2 );

		// add_action( 'init', array( &$this, 'save_categories_blog_main') );
	}

}

$CDbrRepublish = new CDbrRepublish();


?>
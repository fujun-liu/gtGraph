<?
function CCompUnionFindLP_Constant_State(array $t_args)
{
    // Grabbing variables from $t_args
    $className          = $t_args['className'];
?>
using namespace arma;
using namespace std;

class <?=$className?>ConstantState {
 private:
  // The current iteration.
  int iteration;

  // The number of distinct nodes in the graph.
  int num_nodes;

 public:
  friend class <?=$className?>;

  <?=$className?>ConstantState()
      : iteration(0),
        num_nodes(0) {
  }
};

<?
    return [
        'kind' => 'RESOURCE',
        'name' => $className . 'ConstantState',
    ];
}
// component algorithm with label propagation
function CCompUnionFindLP($t_args, $inputs, $outputs)
{
    // Class name is randomly generated.
    $className = generate_name('CCompUnionFindLP');
    // Initializiation of argument names.
    $inputs_ = array_combine(['s', 't'], $inputs);
    $vertex = $inputs_['s'];
    // Construction of outputs.
    $outputs_ = ['node' => $vertex, 'component' => lookupType('int')];
    $outputs = array_combine(array_keys($outputs), $outputs_);
    
    $sys_headers  = ['armadillo', 'algorithm'];
    $user_headers = [];
    $lib_headers  = [];
    $libraries    = ['armadillo'];
    $properties   = [];
    $extra        = [];
    $result_type  = ['multi'];
?>

using namespace arma;
using namespace std;

class <?=$className?>;

<?  $constantState = lookupResource(
        'graph::CCompUnionFindLP_Constant_State',
        ['className' => $className]
    ); ?>

class <?=$className?> {
 public:
  // The constant state for this GLA.
  using ConstantState = <?=$constantState?>;

  // The number of iterations to perform, not counting the initial set-up.
  static const constexpr int kIterations = 5;

  // The work is split into chunks of this size before being partitioned.
  static const constexpr int kBlock = 32;

  // The maximum number of fragments to use.
  static const constexpr int kMaxFragments = 64;

 private:
  
  // Node Component
  static arma::rowvec node_component;
  // keep track of component size
  static arma::rowvec component_size;

  // The typical constant state for an iterable GLA.
  const ConstantState& constant_state;

  // The number of unique nodes seen.
  long num_nodes;

  // 
  long output_iterator;

  // The current iteration.
  int iteration;
  
  // check if need more iterations
  long connections;

 public:
  <?=$className?>(const <?=$constantState?>& state)
      : constant_state(state),
        num_nodes(state.num_nodes),
        iteration(state.iteration),output_iterator(0),connections(0) {
  }

  long Find(long node_id){
    // use path compression here
    while (node_id != node_component(node_id)){
        node_component(node_id) = node_component(node_component(node_id));
        node_id = node_component(node_id);
    }
    return node_id;
  }

  void Union(long pid, long qid){
    // find their root
    pid = Find(pid);
    qid = Find(qid);
    if (pid == qid)
      return;

    ++ connections;
    long psz = component_size(pid), qsz = component_size(qid);
    if (psz > qsz){
      node_component(qid) = pid;
      component_size(pid) += qsz; 
    }else{
      node_component(pid) = qid;
      component_size(qid) += psz; 
    }
  }

  // Basic dynamic array allocation.
  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    if (iteration == 0) {
      num_nodes = max((long) max(s, t), num_nodes);
      return;
    } else{
      Union((long) s, (long) t);
    }
  }

  // Hashes are merged.
  void AddState(<?=$className?> &other) {
    if (iteration == 0)
      num_nodes = max(num_nodes, other.num_nodes);
    else
      connections += other.connections;
  }

  // Most computation that happens at the end of each iteration is parallelized
  // by performed it inside Finalize.
  bool ShouldIterate(ConstantState& state) {
    state.iteration = ++iteration;

    if (iteration == 1) {// allocate memory
      // num_nodes is incremented because IDs are 0-based.
      state.num_nodes = ++num_nodes;
      // Allocating space can't be parallelized.
      node_component.set_size(num_nodes);
      for (long i = 0; i < num_nodes; ++ i){
        node_component(i) = i;
      }
      component_size.set_size(num_nodes);
      component_size.fill(1);
      return true;
    } else {
      return connections > 0 && iteration < kIterations + 1;
    }
  }
  // Finalize does nothing
  void Finalize() {}

  bool GetNextResult(<?=typed_ref_args($outputs_)?>) {
    // should iterate is true
    if (connections > 0 && iteration < kIterations + 1)
      return false;
    
    if(output_iterator < num_nodes){
      node = output_iterator++;
      component = Find(node);
      return true;
    }else{
      return false;
    }
  }
    
};

// Initialize the static member types.
arma::rowvec <?=$className?>::node_component;
arma::rowvec <?=$className?>::component_size;

<?
    return [
        'kind'            => 'GLA',
        'name'            => $className,
        'system_headers'  => $sys_headers,
        'user_headers'    => $user_headers,
        'lib_headers'     => $lib_headers,
        'libraries'       => $libraries,
        'properties'      => $properties,
        'extra'           => $extra,
        'iterable'        => true,
        'input'           => $inputs,
        'output'          => $outputs,
        'result_type'     => $result_type,
        'generated_state' => $constantState,
    ];
}
?>

#ifndef _UNION_FIND_MAP_H_ 
#define _UNION_FIND_MAP_H_
#include <unordered_map>
#include <cstdint>
// node id are assumed to be non-negative uint64_t int 
//template<class uint64_t>
class UnionFindMap{
	private:
		std::unordered_map<uint64_t, uint64_t> parent; 
		std::unordered_map<uint64_t, uint64_t> sz;
		const uint64_t NON_EXISTING_ID = -1;
	public:
		// constructor did nothing
		UnionFindMap(){}

		/*void Init(std::unordered_map<uint64_t, uint64_t>& parent){
			this->parent = parent;
		}*/

		uint64_t Find(uint64_t i){
			if (parent.find(i) == parent.end()){
				return NON_EXISTING_ID;
			}
			// use path compression here
			while (i != parent[i]){
				parent[i] = parent[parent[i]];
				i = parent[i];
			}
			return i;
		}

		// put merge small tree into higher tree
		// if disjoint, merge and return false
		void Union(uint64_t i, uint64_t j){
			uint64_t ip = Find(i);
			uint64_t jp = Find(j);

			if (ip != NON_EXISTING_ID && jp != NON_EXISTING_ID){// both exists
				if (ip != jp){
					if (sz[ip] < sz[jp]){
						parent[ip] = jp; sz[jp] += sz[ip];
					}else{
						parent[jp] = ip; sz[ip] += sz[jp];
					}
				}
			}else if(ip == NON_EXISTING_ID && jp == NON_EXISTING_ID){// both new
				parent[i] = i; sz[i] = 2;
				parent[j] = i; 
			}else if (jp == NON_EXISTING_ID){ // i exists
				parent[j] = ip; sz[ip] ++;
			}else{
				parent[i] = jp; sz[jp] ++;
			}
		}

		std::unordered_map<uint64_t, uint64_t>& GetUF(){
			return parent;
		}

		// 
		void FinalizeRoot(){
			for(std::unordered_map<uint64_t, uint64_t>::iterator it = parent.begin(); it != parent.end(); ++ it){
				it->second = Find(it->first);
			}
		}

		void Clear(){
			parent.clear();
			sz.clear();
		}

};

#endif